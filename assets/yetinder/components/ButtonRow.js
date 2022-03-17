import React from 'react'
import '../yetinder.scss'

const ButtonRow = (props) => {
    return (
        <div className={'col-xl-4 col-md-6'}>
            <div id={'rating-button-group'} className={'btn-group w-100'} role={'group'}>
                <button type={'button'} className={'btn btn-danger'} onClick={() => props.rate(-1)}
                >
                    <i className={'fa-regular fa-thumbs-down'} />
                </button>

                <button type={'button'} className={'btn btn-primary'} onClick={() => props.rate(0)}
                >
                    <i className={'fa-solid fa-question'} />
                </button>

                <button type={'button'} className={'btn btn-success'} onClick={() => props.rate(1)}
                >
                    <i className={'fa-regular fa-thumbs-up'} />
                </button>
            </div>
        </div>
    )
}

export default ButtonRow