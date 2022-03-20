import React, {useEffect, useState} from 'react'
import YetiCard from './components/YetiCard'
import ButtonRow from './components/ButtonRow'

const Root = () => {
    const [errors, setErrors] = useState([])
    const [yetiList, setYetiList] = useState([])
    const [yeti, setYeti] = useState(null)

    const appendErrors = (message) => {
        setErrors(errs => {
            return errs ? errs.concat(message) : [message]
        })
    }

    const incrementYeti = () => {
        const indexOfCurrentYeti = yetiList.indexOf(yeti)
        if (indexOfCurrentYeti >= yetiList.length-1) get()
        else setYeti(yetiList[indexOfCurrentYeti + 1])
    }

    const get = async () => {
        try {
            const resp = await fetch('/yeti/get')
            const respYetiList = await resp.json()
            console.log({
                kde: 'yetinder/Root.js#get',
                log: respYetiList
            })
            setYetiList(respYetiList)
            setYeti(respYetiList[0])
        } catch (e) {
            console.log(e)
        }
    }

    const rate = async (rating) => {
        if (typeof rating !== 'number' || ![-1, 0, 1].includes(rating)) {
            appendErrors('Wrong format of rating');
            return
        }
        const response = await fetch('/yeti/rate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                'yeti_id': yeti.id,
                'rating': rating
            })
        })
        const bodyJson = await response.json()
            .catch(console.log)
        if (response.ok) incrementYeti()
        else if (bodyJson) {
            for (const error of bodyJson) appendErrors(error)
        }
    }

    const constructErrors = () => {
        const container = []
        for (let i = 0; i < errors.length; i++) {
            container.push(<li key={ i } className={'text-danger'}>{ errors[i] }</li>)
        }
        return (<ul>{ container }</ul>)
    }

    useEffect(() => {
        get()
    }, [])

    return (
        <div className={'container-fluid'}>
            { errors && errors.length > 0 ? constructErrors() : null }
            <div className={'row justify-content-center text-center mt-md-5'}>
                {
                    yeti ?
                        <YetiCard yeti={yeti}/>
                        : <h6 className={'display-6'}>
                            Již není koho hodnotit. Zkus to třeba zítra. ;)
                        </h6>
                }
            </div>
            <div className={'row justify-content-center text-center mt-3'}>
                {
                    yeti ?
                        <ButtonRow rate={rate} />
                        : <div>
                            <h5>Ale kdybys chtěl, tak se aspoň můžeš podívat na nějaké <a href={'#'}>Yetistiky</a>!</h5>
                        </div>
                }
            </div>
        </div>
    )
}

export default Root