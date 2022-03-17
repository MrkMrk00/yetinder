import React, {useEffect, useState} from 'react'
import YetiCard from './components/YetiCard'
import ButtonRow from './components/ButtonRow'

const Root = () => {
    const [errors, setErrors] = useState([])
    const [yetiList, setYetiList] = useState([])
    const [yeti, setYeti] = useState(null)

    const get = async () => {
        try {
            const resp = await fetch('/yeti/get')
            const respYetiList = await resp.json()
            console.log(respYetiList)
            setYetiList(respYetiList)
            setYeti(respYetiList[0])
        } catch (e) {
            console.log(e)
        }
    }

    const rate = async (rating) => {
        if (typeof rating !== 'number' || ![-1, 0, 1].includes(rating)) {
            setErrors(errs => {
                const errorMessage = 'Wrong format of rating'
                return errs ? errs.concat(errorMessage) : [errorMessage]
            })
            return
        }
        const reqBody = JSON.stringify({
            'yeti_id': yeti.id,
            'rating': rating
        })
        const response = await fetch('/yeti/rate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: reqBody
        })
        console.log(response.status)
        response.json().then(console.log)
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
            <div className={'row justify-content-center'}>
                { yeti ? <YetiCard yeti={yeti}/> : null }
            </div>
            <div className={'row justify-content-center'}>
                <ButtonRow rate={rate} />
            </div>
        </div>
    )
}

export default Root